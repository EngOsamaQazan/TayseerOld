import { Controller, Get, Param, Query, UseGuards, Request, ParseIntPipe } from '@nestjs/common';
import { ApiBearerAuth, ApiTags, ApiOperation, ApiQuery } from '@nestjs/swagger';
import { AuditService } from './audit.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';

@ApiTags('Audit - سجل العمليات')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('audit')
export class AuditController {
  constructor(private readonly auditService: AuditService) {}

  @Get('recent')
  @ApiOperation({ summary: 'آخر العمليات' })
  @ApiQuery({ name: 'limit', required: false })
  getRecent(@Query('limit') limit = 20, @Request() req: any) {
    return this.auditService.findRecent(req.user.tenantId, +limit);
  }

  @Get('entity/:type/:id')
  @ApiOperation({ summary: 'سجل عمليات كيان محدد' })
  getByEntity(@Param('type') type: string, @Param('id', ParseIntPipe) id: number, @Request() req: any) {
    return this.auditService.findByEntity(req.user.tenantId, type, id);
  }

  @Get('user/:userId')
  @ApiOperation({ summary: 'سجل عمليات مستخدم' })
  @ApiQuery({ name: 'page', required: false })
  getByUser(@Param('userId', ParseIntPipe) userId: number, @Query('page') page = 1, @Request() req: any) {
    return this.auditService.findByUser(req.user.tenantId, userId, +page);
  }
}
